import { chromium } from "playwright-extra";
import { logger } from "../utils/logger.js";
import { extractEmails, extractSocials } from "./emailExtractor.js";

/**
 * Fast Website Crawler with Strict Timeouts.
 * Each call launches its own isolated browser to avoid conflicts with shared instances.
 */
export async function crawlWebsite(url, businessName = "") {
  if (!url || !url.startsWith("http")) return { emails: [], socials: { facebook: null, instagram: null, linkedin: null, twitter: null, youtube: null } };
  
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    locale: "en-IN",
    timezoneId: "Asia/Kolkata",
    userAgent: "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"
  });
  const page = await context.newPage();
  
  try {
    // Phase 1: Homepage (Increased to 15s timeout with networkidle)
    await page.goto(url, { waitUntil: "networkidle", timeout: 15000 });
    let fullHtml = await page.content();
    
    // 📧 Extract emails from mailto: links directly for high reliability
    const mailtoEmails = await page.$$eval("a[href^='mailto:']", links => 
      links.map(l => l.href.replace(/^mailto:/i, "").split("?")[0].toLowerCase())
    );

    // Find contact/about/legal/social/team/follow pages
    const deepLinks = await page.$$eval("a", (links, origin) => {
      const keywords = ["contact", "about", "legal", "social", "team", "follow", "reach", "career", "support", "location", "clinic", "hospital", "info", "get-in-touch", "reach-us", "branches", "appointments", "query"];
      return links
        .map(l => l.href)
        .filter(h => h && h.startsWith(origin)) // Only internal links
        .filter(h => {
          const l = h.toLowerCase();
          return keywords.some(k => l.includes(k));
        });
    }, new URL(url).origin);
    
    const pagesToVisit = [...new Set(deepLinks)].slice(0, 8); // Visit up to 8 deep links
    
    // Phase 2: Deep Links (10s timeout each)
    for (const link of pagesToVisit) {
      try {
        await page.goto(link, { waitUntil: "networkidle", timeout: 10000 });
        fullHtml += " " + await page.content();
        
        // Accumulate mailto: emails from deep links
        const deepMailtos = await page.$$eval("a[href^='mailto:']", lks => 
          lks.map(l => l.href.replace(/^mailto:/i, "").split("?")[0].toLowerCase())
        );
        mailtoEmails.push(...deepMailtos);
      } catch (e) {}
    }
    
    // Results
    const emails = extractEmails(fullHtml, businessName, url);
    const uniqueCombinedEmails = [...new Set([...emails, ...mailtoEmails])];
    
    return { 
      emails: uniqueCombinedEmails,
      socials: extractSocials(fullHtml) 
    };
  } catch (e) {
    logger.error(`Crawl failed for ${url}: ${e.message}`);
    return { emails: [], socials: { facebook: null, instagram: null, linkedin: null, twitter: null, youtube: null } };
  } finally {
    await page.close();
    await context.close().catch(() => {});
    await browser.close().catch(() => {});
  }
}
