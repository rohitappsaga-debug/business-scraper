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
  concurrency: 3, // ? STABILITY: Low concurrency
  maxResults: 25   // ? ACCURACY: High-quality limit
};

async function enrichBusiness(biz, city) {
  let enrichedBiz = { ...biz, email: biz.email || [], socials: biz.socials || {} };

  try {
    // 1. Validated Website Crawl (Accurate source)
    if (enrichedBiz.website) {
      const crawlData = await crawlWebsite(enrichedBiz.website);
      enrichedBiz = mergeBusinessData(enrichedBiz, { 
        email: crawlData.emails, 
        socials: crawlData.socials 
      });
    }

    // 2. Justdial fallback (Phone/Address only)
    if (!enrichedBiz.phone) {
      const jdData = await enrichWithJustdial(enrichedBiz.name, city);
      if (jdData) {
        enrichedBiz = mergeBusinessData(enrichedBiz, jdData);
      }
    }
  } catch (error) {
    // Graceful error handling - skip enrichment if it fails
  }

  return enrichedBiz;
}

async function enrichAllBusinesses(businesses, city) {
  const results = [];
  
  for (let i = 0; i < businesses.length; i += config.concurrency) {
    const batch = businesses.slice(i, i + config.concurrency);
    const enrichedBatch = await Promise.all(
      batch.map(b => enrichBusiness(b, city))
    );
    
    for (const enriched of enrichedBatch) {
      if (enriched.name && enriched.name.length > 1) {
        results.push(enriched);
      }
    }
  }
  
  return results;
}

export async function scrape({ keyword, city }) {
  let rawData = [];

  try {
    // PHASE 1: Accurate Bulk Collection
    const gmapResults = await scrapeGoogleMaps(keyword, city, config.headless);
    
    if (gmapResults.length > 0) {
      rawData = gmapResults.slice(0, config.maxResults);
    } else {
      const roachResult = await scrapeWithRoach({ keyword, city });
      if (roachResult.success) {
        rawData = roachResult.data.slice(0, config.maxResults);
      }
    }

    if (rawData.length === 0) {
      return { success: true, data: [], error: null };
    }

    // PHASE 2: Parallel Enrichment
    const enrichedData = await enrichAllBusinesses(rawData, city);

    return { success: true, data: enrichedData, error: null };
  } catch (err) {
    return { success: false, data: [], error: err.message };
  }
}
