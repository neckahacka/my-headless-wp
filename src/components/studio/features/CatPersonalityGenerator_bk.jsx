// components/studio/features/CatPersonalityGenerator.jsx
import React, { useState } from 'react';

const CatPersonalityGenerator = () => {
  const [personality, setPersonality] = useState(null);
  const [loading, setLoading] = useState(false);

  const traits = {
    temperament: ['Playful', 'Regal', 'Mysterious', 'Adventurous'],
    quirks: ['Loves boxes', 'Chases shadows', 'Meows melodies'],
    style: ['Victorian noble', 'Space explorer', 'Ninja warrior']
  };

  const generatePersonality = async () => {
    setLoading(true);
    // Here you would integrate with AI Power plugin's API
    // For now, we'll simulate the AI response
    await new Promise(resolve => setTimeout(resolve, 1500));
    
    const randomTrait = category => 
      category[Math.floor(Math.random() * category.length)];

    setPersonality({
      temperament: randomTrait(traits.temperament),
      quirk: randomTrait(traits.quirks),
      style: randomTrait(traits.style)
    });
    setLoading(false);
  };

  return (
    <div className="p-6 bg-gray-800 rounded-lg">
      <h2 className="text-2xl font-bold text-white mb-4">Cat Personality Generator</h2>
      
      <button
        onClick={generatePersonality}
        disabled={loading}
        className="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 rounded-lg font-semibold mb-6"
      >
        {loading ? 'Generating...' : 'Generate Cat Personality'}
      </button>

      {personality && (
        <div className="space-y-4">
          <div className="bg-gray-700 p-4 rounded-lg">
            <h3 className="text-purple-400 font-semibold mb-2">Personality Profile</h3>
            <ul className="text-white space-y-2">
              <li>Temperament: {personality.temperament}</li>
              <li>Special Quirk: {personality.quirk}</li>
              <li>Style Inspiration: {personality.style}</li>
            </ul>
          </div>
          
          <button
            className="w-full bg-gray-700 text-white py-2 rounded-lg"
            onClick={() => {
              // Here you would integrate with your art generation
              console.log('Applying personality to art generation...');
            }}
          >
            Apply to Art Generation
          </button>
        </div>
      )}
    </div>
  );
};

export default CatPersonalityGenerator;
