import React, { useState } from "react";
import { generateBackstory } from "../../../services/aiPowerService";
import { Crown } from "lucide-react"; // ✅ Ensure this import exists

const themes = ["Adventure", "Comedy", "Mystery", "Fantasy"];

const CatPersonality = () => {
  const [traits, setTraits] = useState([]);
  const [theme, setTheme] = useState("Adventure");
  const [backstory, setBackstory] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const handleGenerate = async () => {
    if (traits.length === 0) {
      setError("Please select at least one personality trait.");
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const story = await generateBackstory(traits, theme);
      setBackstory(story);
    } catch (err) {
      setError("Failed to generate backstory. Try again.");
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="bg-gray-900 p-8 rounded-xl shadow-lg border border-gray-700/50">
      <div className="flex items-center gap-3 mb-6">
        <div className="p-3 bg-purple-500/10 rounded-lg">
          <Crown className="w-6 h-6 text-yellow-400" />
        </div>
        <h2 className="text-3xl font-bold text-white">Cat Personality Generator</h2>
      </div>

      {/* Theme Selector */}
      <div className="mb-4">
        <label className="text-white block mb-2">Select a Story Theme</label>
        <select
          className="w-full p-2 rounded bg-gray-800 text-white"
          value={theme}
          onChange={(e) => setTheme(e.target.value)}
        >
          {themes.map((t) => (
            <option key={t} value={t}>{t}</option>
          ))}
        </select>
      </div>

      {/* Trait Selection Placeholder (Adjust Based on Your UI) */}
      <div className="mb-4">
        <label className="text-white block mb-2">Personality Traits</label>
        <input
          type="text"
          className="w-full p-2 rounded bg-gray-800 text-white"
          placeholder="E.g., Playful, Mysterious, Brave"
          onChange={(e) => setTraits(e.target.value.split(","))}
        />
      </div>

      {/* Generate Button */}
      <button
        onClick={handleGenerate}
        disabled={loading}
        className="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 
                   text-white py-4 px-6 rounded-lg font-medium shadow-lg 
                   disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 ease-out"
      >
        {loading ? "Generating Story..." : "Generate Backstory"}
      </button>

      {/* Display Backstory */}
      {backstory && (
        <div className="bg-gray-800/30 backdrop-blur-xl p-6 rounded-lg border border-gray-700/50 shadow-md">
          <h3 className="text-purple-400 font-semibold">Generated Story</h3>
          <p className="text-gray-300 mt-2">{backstory}</p>
        </div>
      )}

      {error && <p className="text-red-500 mt-3">{error}</p>}
    </div>
  );
};

export default CatPersonality;
