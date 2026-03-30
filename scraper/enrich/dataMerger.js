export function mergeBusinessData(primary, secondary) {
  const merged = {
    name: primary.name || secondary.name || "",
    category: primary.category || secondary.category || null,
    address: primary.address || secondary.address || "",
    phone: primary.phone || secondary.phone || null,
    website: primary.website || secondary.website || null,
    email: [...new Set([...(primary.email || []), ...(secondary.email || [])])],
    socials: {
      facebook: primary.socials?.facebook || secondary.socials?.facebook || null,
      instagram: primary.socials?.instagram || secondary.socials?.instagram || null,
      linkedin: primary.socials?.linkedin || secondary.socials?.linkedin || null,
      twitter: primary.socials?.twitter || secondary.socials?.twitter || null,
    }
  };

  // 1. Strict Website Validation: Domain must relate to business name
  if (merged.website) {
    const wLow = merged.website.toLowerCase();
    const nameSlug = merged.name.toLowerCase().replace(/[^a-z0-9]/g, "").substring(0, 5);
    
    // Junk check
    if (wLow.includes("maps.google.com") || wLow.includes("google.com/maps/dir")) {
      merged.website = null;
    } 
    // Domain match check (if business is "Kiran", kiranhospital.com is valid, but dentallox.com is not)
    else if (!wLow.includes(nameSlug)) {
      merged.website = null;
    }
  }

  return merged;
}
