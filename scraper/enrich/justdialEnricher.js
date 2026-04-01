import { logger } from "../utils/logger.js";
import { scrapeWithPlaywright } from "../playwrightAdapter.js";

export async function enrichWithJustdial(name, city, browser = null) {
  if (!name || name.length < 2) return null;
  
  try {
    const result = await scrapeWithPlaywright({
      keyword: name,
      city: city,
      headless: true,
      timeout: 15000,
      browser: browser
    });
    
    if (result.success && Array.isArray(result.data) && result.data.length > 0) {
      const targetFirstWord = name.toLowerCase().split(" ")[0];
      const bestMatch = result.data.find(b => 
        b.name && b.name.toLowerCase().includes(targetFirstWord)
      ) || result.data[0];
      
      // ? ACCURACY: Exclude emails and socials from JD
      return {
        name: bestMatch.name,
        address: bestMatch.address,
        phone: bestMatch.phone,
        website: bestMatch.website,
        email: [],
        socials: {}
      };
    }
  } catch (e) {
    // Non-fatal error
  }
  
  return null;
}
