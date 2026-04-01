export function extractEmails(text, businessName = "", website = "") {
  if (!text) return [];
  const regex = /[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/gi;
  const matches = text.match(regex) || [];
  
  const nameSlug = businessName.toLowerCase().replace(/[^a-z0-9]/g, "");
  let domain = "";
  try {
    if (website) domain = new URL(website).hostname.replace("www.", "").split(".")[0];
  } catch (e) {}

  const uniqueEmails = [...new Set(matches.map(e => e.toLowerCase()))];
  
  return uniqueEmails.filter(email => {
    // 🛡️ STABILITY: Allow common business aliases regardless of naming slug
    const isCommonAlias = /^(info|contact|admin|support|office|hello|mail|sales|admission|principal|enquiry)@/i.test(email);
    const isRelatedToDomain = domain && email.includes(domain);
    const isRelatedToName = email.includes(nameSlug) || nameSlug.includes(email.split("@")[0]);
    
    // 🛡️ STABILITY: Only filter out generic providers (gmail/yahoo) if they are totally unrelated to the business
    const isGeneric = /@(gmail|yahoo|outlook|hotmail|mail|icloud)\./i.test(email);
    if (isGeneric) return isRelatedToName || isCommonAlias;

    // Default to including everything else (trusted)
    return true; 
  });
}

export function extractSocials(text, businessName) {
  if (!text) return { facebook: null, instagram: null, linkedin: null, twitter: null };
  const nameSlug = businessName.toLowerCase().replace(/[^a-z0-9]/g, "").substring(0, 4);
  
  const getValidSocial = (regex) => {
    const match = text.match(regex)?.[0] || null;
    // 🛡️ STABILITY: Reduced name slug requirement for social handles as they are often generic
    return match;
  };

  return {
    facebook: getValidSocial(/https?:\/\/(www\.)?facebook\.com\/[^\s"'<]+/i),
    instagram: getValidSocial(/https?:\/\/(www\.)?instagram\.com\/[^\s"'<]+/i),
    linkedin: getValidSocial(/https?:\/\/(www\.)?linkedin\.com\/(in|company)\/[^\s"'<]+/i),
    twitter: getValidSocial(/https?:\/\/(www\.)?(twitter|x)\.com\/[^\s"'<]+/i),
  };
}

