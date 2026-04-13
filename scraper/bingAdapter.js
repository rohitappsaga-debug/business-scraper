import { chromium } from "playwright-extra";
import { logger } from "./utils/logger.js";

/**
 * Scraper for Bing Places results.
 */
export async function scrapeBing({ keyword, city, maxResults = 50 }) {
  const query = `${keyword} in ${city}`;
  const searchUrl = `https://www.bing.com/maps?q=${encodeURIComponent(query)}`;
  
  logger.info(`Bing Scraper: Searching for "${query}"`);
  
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  
  try {
    await page.goto(searchUrl, { waitUntil: "networkidle", timeout: 45000 });
    
    // 🛡️ STABILITY: Handle Bing Cookie Consent
    try {
      const consentButton = page.getByRole('button', { name: /accept|agree|allow/i });
      if (await consentButton.isVisible({ timeout: 5000 })) {
        logger.info("Bing Scraper: Accepting cookie consent...");
        await consentButton.click();
        await page.waitForTimeout(2000);
      }
    } catch (e) {}

    // 🚀 OPTIMIZATION: Updated selectors for Bing Maps
    const resultSelector = ".ent-b_cards, .listing-item, .b_ans"; 
    await page.waitForSelector(resultSelector, { timeout: 10000 }).catch(() => {});
    
    // This is a placeholder for real Bing Maps scraping logic using Playwright
    // In a production app, we would use a more robust way to handle Bing's UI
    const results = await page.evaluate((max) => {
      const bizs = [];
      const items = document.querySelectorAll(".ent-b_cards li, .listing-item, div[role='listitem']");
      
      items.forEach((item, index) => {
        if (index >= max) return;
        
        const name = item.querySelector(".ent-b_title, .listing-title, h1, h2, h3")?.innerText || "";
        const address = item.querySelector(".ent-b_address, .listing-address, [class*='address']")?.innerText || "";
        const phone = item.querySelector(".ent-b_phone, .listing-phone, [class*='phone']")?.innerText || "";
        const website = item.querySelector("a[href*='http'], a.ent-b_website")?.href || "";
        
        if (name) {
          bizs.push({
            name,
            address,
            phone,
            website,
            source: "bing"
          });
        }
      });
      
      return bizs;
    }, maxResults);
    
    return results;
  } catch (error) {
    logger.error(`Bing Scraper failed: ${error.message}`);
    return [];
  } finally {
    await browser.close();
  }
}
