export function extractEmails(text, businessName, website) {
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
    // 1. Strict Validation: Email must relate to domain or business name
    const isRelatedToDomain = domain && email.includes(domain);
    const isRelatedToName = email.includes(nameSlug) || nameSlug.includes(email.split("@")[0]);
    
    // 2. Branded Check: Only allow generic providers if they are branded with the business name
    const isGeneric = /@(gmail|yahoo|outlook|hotmail|mail|icloud)\./i.test(email);
    if (isGeneric) return isRelatedToName;

    return isRelatedToDomain || isRelatedToName;
  });
}

export function extractSocials(text, businessName) {
  if (!text) return { facebook: null, instagram: null, linkedin: null, twitter: null };
  const nameSlug = businessName.toLowerCase().replace(/[^a-z0-9]/g, "").substring(0, 5);
  
  const getValidSocial = (regex) => {
    const match = text.match(regex)?.[0] || null;
    if (match && match.toLowerCase().includes(nameSlug)) return match;
    return null;
  };

  return {
    facebook: getValidSocial(/https?:\/\/(www\.)?facebook\.com\/[^\s"'<]+/i),
    instagram: getValidSocial(/https?:\/\/(www\.)?instagram\.com\/[^\s"'<]+/i),
    linkedin: getValidSocial(/https?:\/\/(www\.)?linkedin\.com\/(in|company)\/[^\s"'<]+/i),
    twitter: getValidSocial(/https?:\/\/(www\.)?(twitter|x)\.com\/[^\s"'<]+/i),
  };
}
