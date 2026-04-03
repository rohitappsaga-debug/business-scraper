export function extractEmails(text, businessName = "", website = "") {
  if (!text) return [];
  
  // 🛡️ OBFUSCATION: Pre-process text to handle common obfuscated formats
  const cleanText = text
    .replace(/\[at\]/gi, "@")
    .replace(/\(at\)/gi, "@")
    .replace(/\[dot\]/gi, ".")
    .replace(/\(dot\)/gi, ".");

  const regex = /[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/gi;
  const matches = cleanText.match(regex) || [];
  
  const nameSlug = businessName.toLowerCase().replace(/[^a-z0-9]/g, "");
  let domain = "";
  try {
    if (website) domain = new URL(website).hostname.replace("www.", "").split(".")[0];
  } catch (e) {}

  const uniqueEmails = [...new Set(matches.map(e => e.toLowerCase()))];
  
  return uniqueEmails.filter(email => {
    // 🛡️ STABILITY: Allow common business aliases
    const isCommonAlias = /^(info|contact|admin|support|office|hello|mail|sales|admission|principal|enquiry|booking|query|help|customercare)@/i.test(email);
    const isRelatedToDomain = domain && email.includes(domain);
    const isRelatedToName = email.includes(nameSlug) || nameSlug.includes(email.split("@")[0]);
    
    // 🛡️ STABILITY: Filter generic providers if unrelated
    const isGeneric = /@(gmail|yahoo|outlook|hotmail|mail|icloud|protonmail|me|live|msn)\./i.test(email);
    if (isGeneric) return isRelatedToName || isCommonAlias;

    return true; 
  });
}

export function extractSocials(text) {
  const socials = { facebook: null, instagram: null, linkedin: null, twitter: null, youtube: null };
  if (!text) return socials;

  const patterns = {
    facebook: /https?:\/\/(www\.)?facebook\.com\/[^\s"'<>\?\&]+/i,
    instagram: /https?:\/\/(www\.)?instagram\.com\/[^\s"'<>\?\&]+/i,
    linkedin: /https?:\/\/(www\.)?linkedin\.com\/(in|company)\/[^\s"'<>\?\&]+/i,
    twitter: /https?:\/\/(www\.)?(twitter|x)\.com\/[^\s"'<>\?\&]+/i,
    youtube: /https?:\/\/(www\.)?youtube\.com\/(c|channel|user|@)?[^\s"'<>\?\&]+/i,
  };

  for (const [platform, pattern] of Object.entries(patterns)) {
    const match = text.match(pattern);
    if (match) {
      socials[platform] = match[0];
    }
  }

  return socials;
}

