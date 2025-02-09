export const generateCatArt = async (prompt, style) => {
    // Enhance the prompt for better results
    const enhancedPrompt = `High quality, detailed image of a cat that is ${prompt}. The image should be in ${style} style, focusing on the cat as the main subject.`;
  
    try {
      const response = await fetch("https://api.openai.com/v1/images/generations", {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${process.env.REACT_APP_OPENAI_API_KEY}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          model: "dall-e-3",
          prompt: enhancedPrompt,
          n: 1,
          size: "1024x1024",
        }),
      });
  
      // Log response for debugging
      const data = await response.json();
      console.log("🔍 OpenAI API Response:", data);
  
      // Error handling for failed API calls
      if (!response.ok || !data.data || data.data.length === 0) {
        throw new Error(`API Error: ${data.error?.message || "Unknown error"}`);
      }
  
      return { url: data.data[0].url }; // Return generated image URL
    } catch (error) {
      console.error("❌ Error generating cat art:", error);
      throw error;
    }
  };
  