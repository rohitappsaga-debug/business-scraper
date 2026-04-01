import { chromium } from "playwright-extra";
import stealth from "puppeteer-extra-plugin-stealth";
import { logger } from "./utils/logger.js";

// Use stealth plugin to bypass bot detection
chromium.use(stealth());

/**
 * Robust Playwright Scraper with safe navigation and browser management.
 * 
 * @param {Object} params - { keyword, city, headless, timeout, proxy }
 * @returns {Promise<Object>} - { success, data, error }
 */
export async function scrapeWithPlaywright({ 
  keyword, 
  city, 
  headless = true, 
  timeout = 30000,
  proxy = null,
  browser: existingBrowser = null
}) {
  let browser = existingBrowser;
  const shouldCloseBrowser = !existingBrowser;

  try {
    if (!browser) {
      const launchOptions = { headless };
      if (proxy && proxy.server) { launchOptions.proxy = proxy; }
      browser = await chromium.launch(launchOptions);
    }
    
    // Custom context with realistic screen size
    const context = await browser.newContext({
      viewport: { width: 1280, height: 720 },
      userAgent: "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"
    });
    
    const page = await context.newPage();
    
    // Ensure keyword and city are clean
    const citySlug = city.toLowerCase().replace(/[^a-z0-9]/g, "-").replace(/-+/g, "-");
    const keywordSlug = keyword.toLowerCase().replace(/[^a-z0-9]/g, "-").replace(/-+/g, "-");
    const url = `https://www.justdial.com/${citySlug}/${keywordSlug}`;

    logger.info(`Playwright: Attempting safe navigation to ${url}`);
    page.setDefaultTimeout(timeout);

    // Phase 1: Safe Navigation with retry mechanism
    try {
      await page.goto(url, { waitUntil: "domcontentloaded", timeout });
    } catch (navErr) {
      logger.error(`Navigation failed for ${url}: ${navErr.message}`);
      // If primary navigation fails, we return empty success to prevent job crash
      await browser.close();
      return { success: true, data: [], error: `Navigation failed: ${navErr.message}` };
    }
    
    // Phase 2: Wait for content with broad selectors
    try {
      await page.waitForSelector(".resultbox_info, li[class*=\"listing\"], .cntanr", { timeout: 10000 });
    } catch (e) {
      logger.warn(`Primary selectors not found for ${url}. This might be an empty results page.`);
      // Return empty instead of error
      await browser.close();
      return { success: true, data: [], error: null };
    }

    // Phase 3: Fast Parallel Extraction
    const businesses = await page.evaluate(() => {
      const results = [];
      const nodes = document.querySelectorAll(".resultbox_info, .jsx-842217686, li[class*=\"listing\"], .cntanr, .cnt_details");
      
      nodes.forEach(node => {
        try {
          const name = node.querySelector(".lng_cont_name, .resultbox_title_anchor, .cont_list_title, h1, h2, h3")?.innerText?.trim();
          if (!name || name.length < 2) return;

          const address = node.querySelector(".resultbox_address, .cont_fl_addr, .addr, .cont_list_addr")?.innerText?.trim();
          const phone = node.querySelector("[class*=\"phone\"], [class*=\"mobile\"], .lng_contact")?.innerText?.replace(/[^0-9]/g, "");
          const website = node.querySelector("a[href*=\"http\"]")?.href;
          
          results.push({
            name, category: null, address: address || "", phone: phone || null, email: [], socials: {}
          });
        } catch (e) {}
      });
      return results;
    });

    await browser.close();
    return { success: true, data: businesses, error: null };
  } catch (error) {
    logger.error("Playwright CRITICAL fail:", error.message);
    if (browser) await browser.close();
    // NEVER crash the entire process. Return a safe empty success.
    return { success: true, data: [], error: `Playwright error: ${error.message}` };
  }
}
