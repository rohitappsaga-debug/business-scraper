import { chromium } from "playwright-extra";
import { logger } from "../utils/logger.js";

/**
 * Scrapes Google Maps in BULK using infinite scroll.
 */
export async function scrapeGoogleMaps(keyword, city, headless = true, existingBrowser = null, maxResults = 50, onResult = null) {
  logger.info(`Google Maps: Bulk searching for ${keyword} in ${city}...`);
  
  let browser = existingBrowser;
  let shouldCloseBrowser = false;
  
  if (!browser) {
    browser = await chromium.launch({ headless });
    shouldCloseBrowser = true;
  }

  const context = await browser.newContext({ locale: "en-US" });
  const page = await context.newPage();
  const results = [];
  
  try {
    const query = encodeURIComponent(`${keyword} in ${city}`);
    const url = `https://www.google.com/maps/search/${query}`;
    
    // 🛡️ STABILITY: Retry logic for main navigation
    let retryCount = 0;
    while(retryCount < 2) {
      try {
        await page.goto(url, { waitUntil: "domcontentloaded", timeout: 45000 });
        // 🛡️ STABILITY: Manual wait for heavy pages instead of networkidle
        await page.waitForTimeout(5000); 
        break;
      } catch (gotoErr) {
        retryCount++;
        if (retryCount >= 2) throw gotoErr;
        logger.warn(`Initial Gmaps navigation failed (${gotoErr.message}). Retrying...`);
        await page.waitForTimeout(3000);
      }
    }
    
    // 🛡️ STABILITY: Handle Google Cookie Consent (Common on fresh profiles)
    try {
      const consentButton = page.getByRole('button', { name: /accept all|agree|consent/i });
      if (await consentButton.isVisible({ timeout: 5000 })) {
        logger.info("Google Maps: Accepting cookie consent...");
        await consentButton.click();
        await page.waitForTimeout(2000);
      }
    } catch (e) {
      // Not always present, ignore if missing
    }
    
    // 🚀 OPTIMIZATION: More robust list detection
    try {
      await page.waitForSelector("div[role='feed'], div[role='main'], a[href*='/maps/place/']", { timeout: 15000 });
      logger.info("Google Maps: Feed found. Starting scroll...");
    } catch (e) {
      await page.screenshot({ path: "scraper_debug_gmaps.png" });
      logger.warn("No Google Maps results found or timeout. Check 'scraper_debug_gmaps.png' to see if blocked.");
      await page.close();
      if (shouldCloseBrowser) await browser.close();
      return [];
    }

    logger.info("Scrolling results panel to maximize data collection...");
    
    // Phase 1: Infinite Scroll (Collect all listing links first, then extract)
    let previousCount = 0;
    let noNewResultsCount = 0;
    let discoveredCount = 0;
    const MAX_NO_NEW_RESULTS = 5;

    // Keep scrolling until we have enough links or Google has no more results
    while (discoveredCount < maxResults) {
      const links = await page.evaluate(() => {
        const selectors = ["a[href*='/maps/place/']", "a.hfpxzc", "a[aria-label][href*='place']"];
        const results = [];
        selectors.forEach(s => {
          document.querySelectorAll(s).forEach(el => {
            if (el.href) results.push(el.href);
          });
        });
        return [...new Set(results)];
      });
      
      discoveredCount = links.length;

      if (discoveredCount > previousCount) {
        noNewResultsCount = 0;
        previousCount = discoveredCount;
        logger.info(`Google Maps: Discovered ${discoveredCount} listings...`);
      } else {
        noNewResultsCount++;
        if (noNewResultsCount >= MAX_NO_NEW_RESULTS) {
          logger.info(`Google Maps: No new results after ${MAX_NO_NEW_RESULTS} scrolls. Stopping.`);
          break;
        }
      }

      // 🛡️ STABILITY: Dynamically find the scrollable container
      await page.evaluate(() => {
        const containers = [
          document.querySelector("div[role='feed']"),
          document.querySelector("div[aria-label^='Results for']"),
          document.querySelector("div[role='main']"),
        ];
        const scrollable = containers.find(c => c && (c.scrollHeight > c.clientHeight));
        if (scrollable) {
          scrollable.scrollBy(0, 5000);
        } else {
          window.scrollBy(0, 2000);
        }
      });
      await page.waitForTimeout(2500);
    }

    const uniqueLinks = await page.evaluate(() => {
      const selectors = ["a[href*='/maps/place/']", "a.hfpxzc", "a[aria-label][href*='place']"];
      const results = [];
      selectors.forEach(s => {
        document.querySelectorAll(s).forEach(el => {
          if (el.href) results.push(el.href);
        });
      });
      return [...new Set(results)];
    });
    
    if (uniqueLinks.length === 0) {
      await page.screenshot({ path: "scraper_debug_gmaps_empty.png" });
      logger.warn("Google Maps: Discovery finished with 0 links. See 'scraper_debug_gmaps_empty.png'");
    }

    logger.info(`Discovery complete. Found ${uniqueLinks.length} unique businesses. Starting detailed extraction...`);

    // PHASE 2: Parallel Extraction with Worker Pool (Tabs)
    const CONCURRENCY = 2; // 🛡️ STABILITY: Reduced concurrency for extraction to ensure success 
    const chunks = [];
    for (let i = 0; i < uniqueLinks.length; i += CONCURRENCY) {
      chunks.push(uniqueLinks.slice(i, i + CONCURRENCY));
    }

    for (const chunk of chunks) {
      await Promise.all(chunk.map(async (link, index) => {
        // ✨ STABILITY: Stronger stagger to avoid detection and race conditions
        await new Promise(resolve => setTimeout(resolve, index * 1200));

        const workerPage = await context.newPage();
        try {
          // 🛡️ STABILITY: Use networkidle for details to ensure info loads completely
          await workerPage.goto(link, { waitUntil: "domcontentloaded", timeout: 25000 });
          await workerPage.waitForSelector("h1", { timeout: 15000 });
          
          const details = await workerPage.evaluate((keyword) => {
            const name = document.querySelector("h1")?.textContent?.trim();
            if (!name || name.toLowerCase().includes("can't reach") || name.toLowerCase().includes("something went wrong")) {
              return null;
            }

            // 🛡️ STABILITY: Multi-layered phone detection (aria-label, data-item-id, regex)
            let phone = null;
            const phoneBtn = document.querySelector("button[data-item-id^='phone:tel:'], button[aria-label^='Phone:']");
            if (phoneBtn) {
              phone = phoneBtn.getAttribute("data-item-id")?.replace("phone:tel:", "") || 
                      phoneBtn.getAttribute("aria-label")?.replace("Phone: ", "")?.trim();
            }

            // 🛡️ STABILITY: Multi-layered website detection
            let website = document.querySelector("a[data-item-id='authority'], a[aria-label^='Website:']")?.getAttribute("href") || null;
            
            if (website && (website.includes("google.com/maps") || !website.startsWith("http"))) {
              website = null;
            }

            const address = document.querySelector("button[data-item-id='address'], button[aria-label^='Address:']")?.getAttribute("aria-label")?.replace("Address: ", "")?.trim() || "";

            return { name, category: keyword, address, phone, website };
          }, keyword);

          if (details && details.name) {
            const biz = {
              ...details,
              email: [],
              socials: { facebook: null, instagram: null, linkedin: null, twitter: null }
            };
            results.push(biz);
            if (onResult) onResult(biz);
          }
        } catch (innerErr) {
          logger.warn(`Extraction failed for ${link}: ${innerErr.message}`);
        } finally {
          await workerPage.close().catch(() => {});
        }
      }));
      logger.info(`Extracted ${results.length} / ${uniqueLinks.length} results...`);
    }
    
    return results;
  } catch (error) {
    logger.error("Google Maps Scraper failed", error.message);
    return results;
  } finally {
    await page.close();
    await context.close();
    if (shouldCloseBrowser && browser) await browser.close();
  }
}
