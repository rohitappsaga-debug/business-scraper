import { scrapeWithRoach } from "./roachAdapter.js";
import { scrapeWithPlaywright } from "./playwrightAdapter.js";
import { scrapeGoogleMaps } from "./enrich/googleMapsDetails.js";
import { crawlWebsite } from "./enrich/websiteCrawler.js";
import { enrichWithJustdial } from "./enrich/justdialEnricher.js";
import { mergeBusinessData } from "./enrich/dataMerger.js";
import { logger } from "./utils/logger.js";

const config = {
  usePlaywrightFallback: true,
  playwrightTimeout: 30000,
  headless: true,
  proxy: null,
  concurrency: 5,  // 🛡 STABILITY: Safer concurrency for local machines
  maxResults: 50   // 📈 VOLUME: Balanced result count
};

async function enrichBusiness(biz, city, browser = null, onResult = null) {
  let enrichedBiz = { ...biz, email: biz.email || [], socials: biz.socials || {} };

  try {
    // 1. Validated Website Crawl (Accurate source)
    // NOTE: We do NOT pass the shared browser here. The shared browser is busy
    // with Google Maps tabs, which causes context conflicts and crashes.
    // websiteCrawler manages its own isolated browser for stability.
    if (enrichedBiz.website) {
      const crawlData = await crawlWebsite(enrichedBiz.website, enrichedBiz.name);
      enrichedBiz = mergeBusinessData(enrichedBiz, { 
        email: crawlData.emails, 
        socials: crawlData.socials 
      });
    }

    // 2. Justdial fallback (Phone/Address only)
    if (!enrichedBiz.phone) {
      const jdData = await enrichWithJustdial(enrichedBiz.name, city, browser);
      if (jdData) {
        enrichedBiz = mergeBusinessData(enrichedBiz, jdData);
      }
    }

    // 💡 NEW: Streaming callback
    if (onResult && enrichedBiz.name) {
      onResult(enrichedBiz);
    }
  } catch (error) {
    // Graceful error handling - skip enrichment if it fails
  }

  return enrichedBiz;
}

async function enrichAllBusinesses(businesses, city, browser = null, onResult = null) {
  const results = [];
  
  for (let i = 0; i < businesses.length; i += config.concurrency) {
    const batch = businesses.slice(i, i + config.concurrency);
    const enrichedBatch = await Promise.all(
      batch.map(b => enrichBusiness(b, city, browser, onResult))
    );
    
    for (const enriched of enrichedBatch) {
      if (enriched.name && enriched.name.length > 1) {
        results.push(enriched);
      }
    }
  }
  
  return results;
}

import { chromium } from "playwright-extra";

export async function scrape({ keyword, city, maxResults = 50, onResult = null }) {
  let rawData = [];
  const browser = await chromium.launch({ headless: config.headless });

  try {
    // PHASE 1: Accurate Bulk Collection (Now uses shared browser)
    // Phase 1: Discover businesses. onResult is NOT passed here because these
    // are raw (unenriched) records. We only stream after full enrichment in Phase 2.
    const gmapResults = await scrapeGoogleMaps(keyword, city, config.headless, browser, maxResults);
    
    if (gmapResults.length > 0) {
      rawData = gmapResults.slice(0, config.maxResults);
    } else {
      const roachResult = await scrapeWithRoach({ keyword, city });
      if (roachResult.success) {
        rawData = roachResult.data.slice(0, config.maxResults);
      }
    }

    if (rawData.length === 0) {
      return [];
    }

    // PHASE 2: Parallel Enrichment (Now uses shared browser instance)
    const enrichedData = await enrichAllBusinesses(rawData, city, browser, onResult);

    return enrichedData;
  } catch (err) {
    return { success: false, data: [], error: err.message };
  } finally {
    await browser.close();
  }
}
