import { chromium } from "playwright-extra";
import { logger } from "../utils/logger.js";

/**
 * Scrapes Google Maps in BULK using infinite scroll.
 */
export async function scrapeGoogleMaps(keyword, city, headless = true) {
  logger.info(`Google Maps: Bulk searching for ${keyword} in ${city}...`);
  const browser = await chromium.launch({ headless });
  const context = await browser.newContext({ locale: "en-US" });
  const page = await context.newPage();
  const results = [];
  
  try {
    const query = encodeURIComponent(`${keyword} in ${city}`);
    await page.goto(`https://www.google.com/maps/search/${query}`, { waitUntil: "domcontentloaded", timeout: 30000 });
    
    // Wait for the results feed to load
    try {
      await page.waitForSelector("div[role='feed']", { timeout: 15000 });
    } catch (e) {
      logger.warn("No Google Maps feed found or timeout.");
      await browser.close();
      return [];
    }

    logger.info("Scrolling results panel to maximize data collection...");
    
    // Phase 1: Infinite Scroll
    let previousCount = 0;
    let noNewResultsCount = 0;
    const MAX_NO_NEW_RESULTS = 4;
    const MAX_TARGET = 300;

    while (results.length < MAX_TARGET) {
      // Extract visible links
      const links = await page.$$eval("a[href*='/maps/place/']", els => els.map(e => e.href));
      const currentCount = links.length;

      if (currentCount > previousCount) {
        noNewResultsCount = 0;
        previousCount = currentCount;
        logger.info(`Loaded ${currentCount} listings so far...`);
      } else {
        noNewResultsCount++;
        if (noNewResultsCount >= MAX_NO_NEW_RESULTS) {
          logger.info("End of results or no new results after multiple scrolls. Stopping scroll.");
          break;
        }
      }

      // Scroll the feed container
      await page.evaluate(() => {
        const feed = document.querySelector("div[role='feed']");
        if (feed) {
          feed.scrollBy(0, 5000);
        }
      });
      
      // Wait for network requests / loading
      await page.waitForTimeout(2000);
    }

    const uniqueLinks = [...new Set(await page.$$eval("a[href*='/maps/place/']", els => els.map(e => e.href)))];
    logger.info(`Bulk Collection complete. Found ${uniqueLinks.length} unique businesses. Starting fast extraction...`);

    // Phase 2: Fast Extraction (Clicking through panels to get exact info)
    for (let i = 0; i < uniqueLinks.length; i++) {
      try {
        const link = uniqueLinks[i];
        await page.goto(link, { waitUntil: "domcontentloaded", timeout: 10000 });
        await page.waitForSelector("h1", { timeout: 5000 });
        
        const name = await page.$eval("h1", el => el.textContent.trim()).catch(() => null);
        if (!name) continue;
        
        const address = await page.$eval("button[data-item-id='address']", el => el.getAttribute("aria-label").replace("Address: ", "").trim()).catch(() => "");
        const phone = await page.$eval("button[data-item-id^='phone:tel:']", el => el.getAttribute("data-item-id").replace("phone:tel:", "")).catch(() => null);
        
        // Extract Valid Website
        let website = await page.$eval("a[data-item-id='authority']", el => el.getAttribute("href")).catch(() => null);
        if (website && (website.includes("google.com/maps") || !website.startsWith("http"))) {
          website = null;
        }

        results.push({
          name, category: keyword, address, phone, website, email: [],
          socials: { facebook: null, instagram: null, linkedin: null, twitter: null }
        });
        
        if (i > 0 && i % 20 === 0) logger.info(`Extracted data for ${i} businesses...`);
        
      } catch (innerErr) {
        // Skip silently to maintain speed
      }
    }
    
    return results;
  } catch (error) {
    logger.error("Google Maps Scraper failed", error.message);
    return results;
  } finally {
    await browser.close();
  }
}
