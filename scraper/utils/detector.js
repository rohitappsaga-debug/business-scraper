
const dynamicDomains = [
  "justdial.com",
  "sulekha.com",
  "yelp.com",
  "google.com/maps",
  "indiamart.com"
];

/**
 * Determines if Playwright should be used as a fallback or primary for dynamic content.
 * 
 * @param {Object} roachResult - Output from roachAdapter.js
 * @param {Object} context - { url, keyword, city }
 * @returns {boolean}
 */
export function shouldUsePlaywright(roachResult, context = {}) {
  // 1. If Roach returned no data, definitely try Playwright
  if (!roachResult || !roachResult.data || roachResult.data.length === 0) {
    return true;
  }

  // 2. If the URL is in a known dynamic domain list
  const url = context.url || "";
  if (dynamicDomains.some(domain => url.toLowerCase().includes(domain))) {
    // If we have few results from Roach, it might be worth trying Playwright
    if (roachResult.data.length < 3) {
      return true;
    }
  }

  // 3. Optional: Detect if page body has too many scripts vs content
  // (In practice, we usually know if a site needs JS)
  
  return false;
}

