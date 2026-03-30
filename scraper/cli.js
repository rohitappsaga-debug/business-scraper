import { scrape } from "./scraperOrchestrator.js";

async function main() {
  const keyword = process.argv[2];
  const city = process.argv[3];

  if (!keyword || !city) {
    process.stdout.write(JSON.stringify({ success: false, data: [], error: "Missing keyword or city" }));
    process.exit(1);
  }

  try {
    const result = await scrape({ keyword, city });
    
    // Output strictly one JSON line to stdout
    process.stdout.write(JSON.stringify(result));
    process.exit(0);
  } catch (error) {
    // If everything crashes, return a valid JSON object fallback
    process.stdout.write(JSON.stringify({ 
      success: false, 
      data: [], 
      error: `CRITICAL_ERROR: ${error.message}` 
    }));
    process.exit(1);
  }
}

main();
