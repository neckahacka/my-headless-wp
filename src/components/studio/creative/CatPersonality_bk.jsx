import React, { useState } from 'react';
import { Crown } from 'lucide-react';

const CatPersonality = () => {
  const [personality, setPersonality] = useState(null);
  const [loading, setLoading] = useState(false);

  const generatePersonality = async () => {
    setLoading(true);
    try {
      // AI integration will go here
      setPersonality({
        traits: ['Playful', 'Mysterious', 'Elegant'],
        backstory: 'A sophisticated feline with a taste for adventure...'
      });
    } catch (error) {
      console.error('Failed to generate personality:', error);
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
      
      <button
        onClick={generatePersonality}
        disabled={loading}
        className="w-full py-3 rounded-lg bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold"
      >
        {loading ? 'Generating...' : 'Generate Personality'}
      </button>

      {personality && (
        <div className="mt-6 space-y-4">
          <div className="bg-gray-700 rounded-lg p-4">
            <h3 className="text-purple-400 font-semibold mb-2">Traits</h3>
            <div className="flex gap-2">
              {personality.traits.map((trait, index) => (
                <span key={index} className="px-3 py-1 bg-gray-600 rounded-full text-white text-sm">
                  {trait}
                </span>
              ))}
            </div>
          </div>
          
          <div className="bg-gray-700 rounded-lg p-4">
            <h3 className="text-purple-400 font-semibold mb-2">Backstory</h3>
            <p className="text-gray-300">{personality.backstory}</p>
          </div>
        </div>
      )}
    </div>
  );
};

export default CatPersonality;
