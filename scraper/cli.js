import { scrape, discoverWebsiteUrl } from "./scraperOrchestrator.js";

async function main() {
  const keyword = process.argv[2];
  const city = process.argv[3];
  const mode = process.argv.find(arg => arg.startsWith("--mode="))?.split("=")[1] || "scrape";

  if (!keyword || !city) {
    process.stdout.write(JSON.stringify({ success: false, data: [], error: "Missing keyword or city" }));
    process.exit(1);
  }

  try {
    if (mode === "discover") {
      // 🔎 DISCOVERY: Find official website for a specific business
      const website = await discoverWebsiteUrl(keyword, city);
      process.stdout.write("_DISCOVERY_RESULT_:" + JSON.stringify({ website }) + "\n");
      process.exit(0);
    }

    if (mode === "enrich-url") {
      // 🔗 ENRICH: Deep crawl a specific URL for emails and social links
      const url = keyword; // Reuse keyword param for URL
      const { crawlWebsite } = await import("./enrich/websiteCrawler.js");
      const result = await crawlWebsite(url, city); // Reuse city param for businessName
      process.stdout.write("_ENRICH_RESULT_:" + JSON.stringify(result) + "\n");
      process.exit(0);
    }

    const maxResults = process.argv[4] ? parseInt(process.argv[4]) : undefined;
    
    // 💡 NEW: We now pass a callback to scrape() to handle streaming results
    const result = await scrape({ 
      keyword, 
      city,
      maxResults,
      onResult: (biz) => {
        // Output each result immediately as a separate JSON line
        process.stdout.write("_STREAM_ROW_:" + JSON.stringify(biz) + "\n");
      }
    });
    
    // Final completion marker with summary
    process.stdout.write("\n_JSON_START_\n" + JSON.stringify({ success: true, count: result.length }) + "\n_JSON_END_\n");
    process.exit(0);
  } catch (error) {
    process.stdout.write("\n_JSON_START_\n" + JSON.stringify({ 
      success: false, 
      data: [], 
      error: `CRITICAL_ERROR: ${error.message}` 
    }) + "\n_JSON_END_\n");
    process.exit(1);
  }
}

main();

