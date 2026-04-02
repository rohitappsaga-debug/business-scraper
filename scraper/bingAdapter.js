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
    
    // Wait for result list items
    const resultSelector = ".ent-b_cards"; // Update this with real selector from Bing Maps
    // For now, use a common selector analysis or generic search approach
    
    // This is a placeholder for real Bing Maps scraping logic using Playwright
    // In a production app, we would use a more robust way to handle Bing's UI
    const results = await page.evaluate((max) => {
      const bizs = [];
      const items = document.querySelectorAll(".ent-b_cards li"); // Simplified selector
      
      items.forEach((item, index) => {
        if (index >= max) return;
        
        const name = item.querySelector(".ent-b_title")?.innerText || "";
        const address = item.querySelector(".ent-b_address")?.innerText || "";
        const phone = item.querySelector(".ent-b_phone")?.innerText || "";
        const website = item.querySelector("a.ent-b_website")?.href || "";
        
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
