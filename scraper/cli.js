import { scrape } from "./scraperOrchestrator.js";

async function main() {
  const keyword = process.argv[2];
  const city = process.argv[3];

  if (!keyword || !city) {
    process.stdout.write(JSON.stringify({ success: false, data: [], error: "Missing keyword or city" }));
    process.exit(1);
  }

  try {
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
