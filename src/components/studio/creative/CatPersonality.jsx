import React, { useState } from "react";
import { Crown, Sparkles, Edit } from "lucide-react";

const predefinedTraits = [
  "Playful",
  "Mysterious",
  "Elegant",
  "Curious",
  "Grumpy",
  "Adventurous",
  "Lazy",
  "Affectionate",
];

const CatPersonality = () => {
  const [personality, setPersonality] = useState({ traits: [], backstory: "" });
  const [loading, setLoading] = useState(false);
  const [editing, setEditing] = useState(false);
  const [customTraits, setCustomTraits] = useState([]);

  const generateTraits = () => {
    const numTraits = Math.floor(Math.random() * 3) + 2; // 2-4 traits
    const selectedTraits = new Set();
    while (selectedTraits.size < numTraits) {
      const randomTrait = predefinedTraits[Math.floor(Math.random() * predefinedTraits.length)];
      selectedTraits.add(randomTrait);
    }
    setPersonality((prev) => ({ ...prev, traits: Array.from(selectedTraits) }));
  };

  const generateBackstory = async () => {
    setLoading(true);
    console.log("🟢 Sending API Request...");

    try {
      const response = await fetch("https://api.openai.com/v1/chat/completions", {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${process.env.REACT_APP_OPENAI_API_KEY}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          model: "gpt-4",
          messages: [{ role: "system", content: `Create a short, fun backstory for a cat with traits: ${personality.traits.join(", ")}.` }],
          max_tokens: 50,
        }),
      });

      console.log("🟢 API Request Sent. Awaiting response...");
      
      // ✅ Log full response before parsing
      const data = await response.json();
      console.log("🔍 API Response:", data);

      // ✅ Check if response contains choices
      if (!data.choices || !Array.isArray(data.choices) || data.choices.length === 0) {
        console.error("❌ API Response Missing `choices`:", data);
        alert("Error: OpenAI API did not return a valid response. Check API key and request.");
        return;
      }

      // ✅ Set backstory only if data.choices exists
      setPersonality((prev) => ({
        ...prev,
        backstory: data.choices[0].message.content.trim(),
      }));
    } catch (error) {
      console.error("❌ API Request Failed:", error);
      alert("Error generating backstory. Check API settings.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="bg-gray-800 rounded-lg p-6">
      <div className="flex items-center gap-3 mb-6">
        <Crown className="w-6 h-6 text-purple-400" />
        <h2 className="text-2xl font-bold text-white">Cat Personality Generator</h2>
      </div>

      <button onClick={generateTraits} className="w-full py-3 rounded-lg bg-purple-600 text-white mb-3">
        Generate Traits
      </button>
      {personality.traits.length > 0 && (
        <div className="bg-gray-700 rounded-lg p-4">
          <h3 className="text-purple-400 font-semibold mb-2">Traits</h3>
          <div className="flex gap-2 flex-wrap">
            {personality.traits.map((trait, index) => (
              <span key={index} className="px-3 py-1 bg-gray-600 rounded-full text-white text-sm">
                {trait}
              </span>
            ))}
          </div>
        </div>
      )}

      <button
        onClick={generateBackstory}
        disabled={loading || personality.traits.length === 0}
        className="w-full py-3 rounded-lg bg-pink-600 text-white mt-3"
      >
        {loading ? "Generating Backstory..." : "Generate Backstory"}
      </button>
      {personality.backstory && (
        <div className="bg-gray-700 rounded-lg p-4 mt-3">
          <div className="flex justify-between items-center">
            <h3 className="text-purple-400 font-semibold">Backstory</h3>
            <button onClick={() => setEditing(!editing)} className="text-white">
              <Edit className="w-5 h-5" />
            </button>
          </div>
          {editing ? (
            <textarea
              className="w-full bg-gray-600 text-white rounded p-2 mt-2"
              value={personality.backstory}
              onChange={(e) => setPersonality({ ...personality, backstory: e.target.value })}
            />
          ) : (
            <p className="text-gray-300 mt-2">{personality.backstory}</p>
          )}
        </div>
      )}
    </div>
  );
};

export default CatPersonality;
