
import { scrapeWithPlaywright } from "./playwrightAdapter.js";
import { logger } from "./utils/logger.js";

async function test() {
  const keyword = process.argv[2] || "hospital";
  const city = process.argv[3] || "surat";
  
  logger.info(`Testing Playwright ONLY for: ${keyword} in ${city}`);
  
  const result = await scrapeWithPlaywright({
    keyword,
    city,
    headless: false, // Set to false so you can WATCH the browser work!
    timeout: 60000
  });

  if (result.success) {
    logger.info(`Found ${result.data.length} results.`);
    if (result.data.length === 0) {
      logger.warn("Check 'scraper_debug_gmaps.png' or 'scraper_debug_gmaps_empty.png' to see why 0 results were found.");
    }
    console.table(result.data.slice(0, 10));
  } else {
    logger.error(`Playwright failed: ${result.error}`);
  }
}

test();

