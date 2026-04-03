export const logger = {
  info: (msg, data = "") => console.error(`[INFO] ${msg}`, data),
  error: (msg, error = "") => console.error(`[ERROR] ${msg}`, error),
  warn: (msg, data = "") => console.error(`[WARN] ${msg}`, data)
};
