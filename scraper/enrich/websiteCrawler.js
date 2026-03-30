import { chromium } from "playwright-extra";
import { logger } from "../utils/logger.js";
import { extractEmails, extractSocials } from "./emailExtractor.js";

/**
 * Fast Website Crawler with Strict Timeouts.
 */
export async function crawlWebsite(url, browserInstance = null) {
  if (!url || !url.startsWith("http")) return { emails: [], socials: { facebook: null, instagram: null, linkedin: null, twitter: null } };
  
  let browser = browserInstance;
  let shouldCloseBrowser = false;
  
  if (!browser) {
    browser = await chromium.launch({ headless: true });
    shouldCloseBrowser = true;
  }
  
  const context = await browser.newContext();
  const page = await context.newPage();
  let textContent = "";
  
  try {
    // Phase 1: Homepage (Strict 10s timeout)
    await page.goto(url, { waitUntil: "domcontentloaded", timeout: 10000 });
    textContent += await page.content();
    
    // Find contact/about pages
    const contactLinks = await page.$$eval("a", links => 
      links.map(l => l.href).filter(h => h.toLowerCase().includes("contact") || h.toLowerCase().includes("about"))
    );
    
    const pagesToVisit = [...new Set(contactLinks)].slice(0, 1); // Only visit 1 deep link for speed
    
    // Phase 2: Deep Link (Strict 8s timeout)
    for (const link of pagesToVisit) {
      try {
        await page.goto(link, { waitUntil: "domcontentloaded", timeout: 8000 });
        textContent += await page.content();
      } catch (e) {
        // Ignore deep link timeout
      }
    }
  } catch (e) {
    // Fail silently on main timeout to keep concurrency flowing
  } finally {
    await page.close();
    if (shouldCloseBrowser) await browser.close();
  }
  
  return { emails: extractEmails(textContent), socials: extractSocials(textContent) };
}
