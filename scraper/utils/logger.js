export const logger = {
  info: (msg, data = "") => { /* Silent in production or redirect to stderr */ },
  error: (msg, error = "") => console.error(`[ERROR] ${msg}`, error),
  warn: (msg, data = "") => console.error(`[WARN] ${msg}`, data)
};
