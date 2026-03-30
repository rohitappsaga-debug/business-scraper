
import { scrape } from "./scraperOrchestrator.js";
import { logger } from "./utils/logger.js";

async function main() {
  const keyword = process.argv[2] || "hospital";
  const city = process.argv[3] || "kamrej";

  logger.info(`Starting hybrid test with keyword: ${keyword}, city: ${city}`);

  try {
    const result = await scrape({ keyword, city });
    if (result.success) {
      logger.info(`Successfully found ${result.data.length} businesses.`);
      // Log first few results
      console.log(JSON.stringify(result.data.slice(0, 3), null, 2));
    } else {
      logger.error(`Hybrid scraping failed: ${result.error}`);
    }
  } catch (error) {
    logger.error(`Unhandled error during test: ${error.message}`);
  }
}

main();

