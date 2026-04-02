import { scrapeWithRoach } from "./roachAdapter.js";
import { scrapeWithPlaywright } from "./playwrightAdapter.js";
import { scrapeGoogleMaps } from "./enrich/googleMapsDetails.js";
import { crawlWebsite } from "./enrich/websiteCrawler.js";
import { enrichWithJustdial } from "./enrich/justdialEnricher.js";
import { mergeBusinessData } from "./enrich/dataMerger.js";
import { logger } from "./utils/logger.js";
import { scrapeBing } from "./bingAdapter.js";
import { scrapeOSM } from "./osmAdapter.js";

const config = {
  usePlaywrightFallback: true,
  playwrightTimeout: 30000,
  headless: true,
  proxy: null,
  concurrency: 5,  // 🛡 STABILITY: Safer concurrency for local machines
  maxResults: 100  // 📈 VOLUME: Increased for multi-source
};

/**
 * Deduplicate raw results from multiple sources.
 */
function deduplicateRaw(results) {
  const seen = new Set();
  return results.filter(biz => {
    const key = `${biz.name?.toLowerCase()}|${(biz.phone || biz.address)?.toLowerCase()}`;
    if (seen.has(key)) return false;
    seen.add(key);
    return true;
  });
}

async function enrichBusiness(biz, city, browser = null, onResult = null) {
  let enrichedBiz = { ...biz, email: biz.email || [], socials: biz.socials || {} };

  try {
    // 1. Validated Website Crawl (Accurate source)
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

export async function scrape({ keyword, city, maxResults = 100, onResult = null }) {
  const browser = await chromium.launch({ headless: config.headless });

  try {
    // PHASE 1: Multi-Source Parallel Collection
    logger.info(`Starting Multi-Source Scraping: ${keyword} in ${city}`);
    
    const [gmapResults, bingResults, osmResults] = await Promise.all([
      scrapeGoogleMaps(keyword, city, config.headless, browser, maxResults).catch(e => []),
      scrapeBing({ keyword, city, maxResults: 30 }).catch(e => []),
      scrapeOSM({ keyword, city, maxResults: 30 }).catch(e => [])
    ]);

    let combinedRaw = [...gmapResults, ...bingResults, ...osmResults];
    
    if (combinedRaw.length === 0) {
      // Fallback to Roach if all else fails
      const roachResult = await scrapeWithRoach({ keyword, city });
      if (roachResult.success) {
        combinedRaw = roachResult.data;
      }
    }

    const rawData = deduplicateRaw(combinedRaw).slice(0, maxResults || config.maxResults);

    logger.info(`Phase 1 Complete: Found ${rawData.length} unique businesses from multiple sources`);

    // 💡 NEW: Stream raw results immediately to Laravel
    if (onResult) {
      for (const biz of rawData) {
        onResult(biz);
      }
    }

    // ⚡ OPTIMIZATION: Return raw data immediately.
    // Deep enrichment (crawling websites, social handles, corporate emails) 
    // is now offloaded to the background queue to keep discovery high-speed.
    return rawData;
  } catch (err) {
    logger.error(`Orchestrator failed: ${err.message}`);
    return { success: false, data: [], error: err.message };
  } finally {
    await browser.close();
  }
}
