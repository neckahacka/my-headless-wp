import React, { useState } from "react";
import { Sparkles, Loader, Cat } from "lucide-react";
import { generateCatArt } from "../../../services/aiPowerService"; // ✅ Corrected import path
import { NavLink } from "react-router-dom";

// PromptBuilder Component
const PromptBuilder = ({ value, onChange, disabled }) => {
  return (
    <div className="space-y-2">
      <textarea
        value={value}
        onChange={(e) => onChange(e.target.value)}
        disabled={disabled}
        placeholder="Describe your dream cat (e.g., 'A majestic space-traveling cat with cosmic whiskers')"
        className="w-full h-24 px-4 py-2 bg-gray-900/50 text-white rounded-lg 
                   border border-gray-700 focus:border-purple-500 focus:ring-1 focus:ring-purple-500
                   disabled:opacity-50 disabled:cursor-not-allowed
                   placeholder-gray-500"
      />
    </div>
  );
};

// StyleSelector Component
const StyleSelector = ({ value, onChange, disabled }) => {
  const styles = [
    "Realistic", "Cartoon", "Watercolor", "Pixel Art",
    "Oil Painting", "Cyberpunk", "Fantasy", "Studio Ghibli"
  ];

  return (
    <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
      {styles.map((style) => (
        <button
          key={style}
          onClick={() => onChange(style)}
          disabled={disabled}
          className={`p-3 rounded-lg text-sm transition-all duration-200
                     ${value === style 
                       ? 'bg-purple-600 text-white' 
                       : 'bg-gray-800 text-gray-300 hover:bg-gray-700'}
                     disabled:opacity-50 disabled:cursor-not-allowed`}
        >
          {style}
        </button>
      ))}
    </div>
  );
};

// GenerationCanvas Component
const GenerationCanvas = ({ imageUrl, loading, error }) => {
  if (loading) {
    return (
      <div className="flex items-center justify-center h-64 bg-gray-900/50 rounded-lg">
        <div className="text-center text-gray-400">
          <Loader className="w-8 h-8 mb-4 mx-auto animate-spin" />
          <p>Creating your masterpiece...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center h-64 bg-gray-900/50 rounded-lg">
        <div className="text-center text-red-400">
          <p>{error}</p>
        </div>
      </div>
    );
  }

  if (!imageUrl) {
    return (
      <div className="flex items-center justify-center h-64 bg-gray-900/50 rounded-lg">
        <div className="text-center text-gray-400">
          <Sparkles className="w-8 h-8 mb-4 mx-auto" />
          <p>Your cat masterpiece will appear here</p>
        </div>
      </div>
    );
  }

  return (
    <div className="relative aspect-square rounded-lg overflow-hidden">
      <img
        src={imageUrl}
        alt="Generated cat art"
        className="w-full h-full object-cover"
      />
    </div>
  );
};

// Main ArtGenerator Component
const ArtGenerator = () => {
  const [prompt, setPrompt] = useState("");
  const [style, setStyle] = useState("");
  const [imageURL, setImageURL] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // ✅ Corrected API Call for OpenAI Integration
  const handleGenerate = async () => {
    if (!prompt) {
      setError("Please enter a description for your cat!");
      return;
    }

    setLoading(true);
    setError(null);
    setImageURL(null);

    try {
      const result = await generateCatArt(prompt, style);
      setImageURL(result.url); // ✅ Set generated image from OpenAI API
    } catch (err) {
      setError("Failed to generate image. Please try again.");
      console.error("Error generating cat art:", err);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-4xl mx-auto">
      <div className="absolute inset-0 overflow-hidden pointer-events-none">
        <div className="absolute w-40 h-40 bg-purple-500/10 rounded-full blur-3xl -top-20 -left-20 animate-blob" />
        <div className="absolute w-40 h-40 bg-blue-500/10 rounded-full blur-3xl top-40 right-20 animate-blob animation-delay-2000" />
        <div className="absolute w-40 h-40 bg-pink-500/10 rounded-full blur-3xl bottom-20 left-20 animate-blob animation-delay-4000" />
      </div>

      <div className="relative bg-gradient-to-br from-gray-900 to-gray-800 p-8 rounded-xl shadow-2xl border border-gray-700/50">
        <div className="flex items-center gap-3 mb-8">
          <div className="p-3 bg-purple-500/10 rounded-lg">
            <Cat className="w-8 h-8 text-purple-400" />
          </div>
          <div>
            <h2 className="text-3xl font-bold text-white">Cat Art Generator</h2>
            <p className="text-gray-400">Create unique AI-generated cat artwork</p>
          </div>
        </div>

        <div className="space-y-6">
          <PromptBuilder value={prompt} onChange={setPrompt} disabled={loading} />
          <StyleSelector value={style} onChange={setStyle} disabled={loading} />

          {/* ✅ Corrected onClick Function */}
          <button
            onClick={handleGenerate}
            disabled={loading}
            className="group w-full bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 
                     text-white py-4 px-6 rounded-lg font-medium shadow-lg 
                     disabled:opacity-50 disabled:cursor-not-allowed
                     transition-all duration-300 ease-out
                     flex items-center justify-center gap-2 relative overflow-hidden"
          >
            <div className="absolute inset-0 bg-white/10 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-1000 ease-out" />
            {loading ? (
              <>
                <Loader className="w-5 h-5 animate-spin" />
                <span>Creating Magic...</span>
              </>
            ) : (
              <>
                <Sparkles className="w-5 h-5" />
                <span>Generate Art</span>
              </>
            )}
          </button>

          <GenerationCanvas imageUrl={imageURL} loading={loading} error={error} />
        </div>
      </div>
    </div>
  );
};

export const STUDIO_TOOLS = [
  { name: 'Cat Personality Generator', path: '/studio/personality' },
  { name: 'Art Generator', path: '/studio/create' },  // ArtGenerator added
  { name: 'Style Transfer', path: '/studio/transform' },
];

export default ArtGenerator;

// Navigation Component
const Navigation = () => {
  return (
    <nav className="space-x-4">
      {STUDIO_TOOLS.map((tool) => (
        <NavLink
          key={tool.path}
          to={tool.path}
          className="text-white hover:text-purple-400"
        >
          {tool.name}
        </NavLink>
      ))}
    </nav>
  );
};

export { Navigation };
