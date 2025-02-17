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

export const generateBackstory = async (traits, theme) => {
  // Improve the AI prompt for better storytelling
  const enhancedPrompt = `Write a rich, engaging, and immersive ${theme.toLowerCase()} story about a cat with the following traits: ${traits.join(", ")}. 
  The story should be 4-5 sentences long and give the cat a unique personality, setting, and adventure.`;

  try {
    const response = await fetch("https://api.openai.com/v1/chat/completions", {
      method: "POST",
      headers: {
        "Authorization": `Bearer ${process.env.REACT_APP_OPENAI_API_KEY}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        model: "gpt-4",
        messages: [{ role: "system", content: enhancedPrompt }],
        max_tokens: 150,  // Ensure a detailed response
      }),
    });

    const data = await response.json();
    console.log("🔍 AI Backstory Response:", data);

    if (!response.ok || !data.choices || data.choices.length === 0) {
      throw new Error(`API Error: ${data.error?.message || "Unknown error"}`);
    }

    return data.choices[0].message.content.trim(); // Return the generated story
  } catch (error) {
    console.error("❌ Error generating backstory:", error);
    throw error;
  }
};
