
import { exec } from "child_process";
import { promisify } from "util";
import path from "path";
import { fileURLToPath } from "url";

const execAsync = promisify(exec);
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const artisanPath = path.resolve(__dirname, "../artisan");

/**
 * Executes the Roach PHP scraper via Laravel Artisan.
 * 
 * @param {Object} params - { keyword, city }
 * @returns {Promise<Object>} - { success, data, error }
 */
export async function scrapeWithRoach({ keyword, city }) {
  try {
    const cmd = `php "${artisanPath}" app:run-roach "${keyword}" "${city}"`;
    const { stdout, stderr } = await execAsync(cmd);

    if (stderr && !stdout) {
      return { success: false, data: [], error: stderr };
    }

    try {
      const result = JSON.parse(stdout);
      return {
        success: result.success || false,
        data: result.data || [],
        error: result.error || null
      };
    } catch (parseError) {
      return { 
        success: false, 
        data: [], 
        error: `Failed to parse Roach output: ${parseError.message}. Raw output: ${stdout}` 
      };
    }
  } catch (error) {
    return { success: false, data: [], error: error.message };
  }
}

