import axios from "axios";
import { logger } from "./utils/logger.js";

/**
 * OpenStreetMap (OSM) Adapter using Overpass API.
 * Faster and more reliable than scraping for generic geographic business data.
 */
export async function scrapeOSM({ keyword, city, maxResults = 50 }) {
  const overpassUrl = "https://overpass-api.de/api/interpreter";
  
    [out:json][timeout:25];
    area["name"="${city}"]["admin_level"~"4|6|8"]->.searchArea;
    (
      node["name"~"${keyword}",i](area.searchArea);
      way["name"~"${keyword}",i](area.searchArea);
      node["amenity"~"${keyword}",i](area.searchArea);
      way["amenity"~"${keyword}",i](area.searchArea);
      node["shop"~"${keyword}",i](area.searchArea);
      way["shop"~"${keyword}",i](area.searchArea);
    );
    out body;
    >;
    out skel qt;
  `;
  
  logger.info(`OSM Scraper: Querying Overpass API for "${keyword}" in "${city}"`);
  
  try {
    const response = await axios.post(overpassUrl, `data=${encodeURIComponent(query)}`, {
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      timeout: 30000
    });
    
    const elements = response.data.elements || [];
    const results = elements
      .filter(el => el.tags && (el.tags.name || el.tags.amenity))
      .slice(0, maxResults)
      .map(el => {
        const tags = el.tags;
        return {
          name: tags.name || tags.amenity,
          category: tags.amenity || tags.shop || tags.office || tags.leisure,
          address: [
            tags["addr:housenumber"],
            tags["addr:street"],
            tags["addr:suburb"],
            tags["addr:city"],
            tags["addr:postcode"]
          ].filter(Boolean).join(", "),
          phone: tags.phone || tags["contact:phone"],
          website: tags.website || tags["contact:website"],
          latitude: el.lat || (el.center ? el.center.lat : null),
          longitude: el.lon || (el.center ? el.center.lon : null),
          source: "osm"
        };
      });
      
    return results;
  } catch (error) {
    logger.error(`OSM Scraper failed: ${error.message}`);
    return [];
  }
}
