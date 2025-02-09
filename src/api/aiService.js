// src/services/aiService.js
export const generateCatPersonality = async (prompt) => {
    // Connect to AI Power plugin's API
    const response = await fetch('/wp-json/ai-power/v1/generate', {
        method: 'POST',
        body: JSON.stringify({
            prompt,
            model: 'gpt-4',
            type: 'personality'
        })
    });
    return response.json();
};