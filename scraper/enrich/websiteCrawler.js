import { chromium } from "playwright-extra";
import { logger } from "../utils/logger.js";
import { extractEmails, extractSocials } from "./emailExtractor.js";

/**
 * Fast Website Crawler with Strict Timeouts.
 * Each call launches its own isolated browser to avoid conflicts with shared instances.
 */
export async function crawlWebsite(url, businessName = "") {
  if (!url || !url.startsWith("http")) return { emails: [], socials: { facebook: null, instagram: null, linkedin: null, twitter: null } };
  
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();
  let textContent = "";
  
  try {
    // Phase 1: Homepage (Strict 10s timeout)
    await page.goto(url, { waitUntil: "domcontentloaded", timeout: 10000 });
    textContent += await page.content();
    
    // Find contact/about/legal/social/team/follow pages
    const deepLinks = await page.$$eval("a", (links, origin) => {
      return links
        .map(l => l.href)
        .filter(h => h.startsWith(origin)) // Only internal links
        .filter(h => {
          const l = h.toLowerCase();
          return l.includes("contact") || l.includes("about") || l.includes("legal") || l.includes("social") || l.includes("team") || l.includes("follow");
        });
    }, new URL(url).origin);
    
    const pagesToVisit = [...new Set(deepLinks)].slice(0, 3); // Visit up to 3 deep links
    
    // Phase 2: Deep Links (Strict 8s timeout each)
    for (const link of pagesToVisit) {
      try {
        await page.goto(link, { waitUntil: "domcontentloaded", timeout: 8000 });
        textContent += " " + await page.content();
      } catch (e) {}
    }
  } catch (e) {
    // Fail silently on main timeout
  } finally {
    await page.close();
    await context.close().catch(() => {});
    await browser.close().catch(() => {});
  }
  
  return { 
    emails: extractEmails(textContent, businessName, url), 
    socials: extractSocials(textContent, businessName) 
  };
}
