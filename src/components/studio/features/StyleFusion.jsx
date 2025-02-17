// components/studio/features/StyleFusion.jsx
import React, { useState } from 'react';

const StyleFusion = () => {
  const [selectedStyles, setSelectedStyles] = useState([]);
  
  const availableStyles = [
    {
      id: 'watercolor',
      name: 'Watercolor',
      strength: 50
    },
    {
      id: 'anime',
      name: 'Anime',
      strength: 50
    },
    {
      id: 'vintage',
      name: 'Vintage Photo',
      strength: 50
    }
  ];

  const updateStyleStrength = (styleId, strength) => {
    setSelectedStyles(styles => 
      styles.map(style => 
        style.id === styleId 
          ? { ...style, strength } 
          : style
      )
    );
  };

  return (
    <div className="p-6 bg-gray-800 rounded-lg">
      <h2 className="text-2xl font-bold text-white mb-4">Style Fusion</h2>
      
      <div className="space-y-4 mb-6">
        {availableStyles.map(style => (
          <div key={style.id} className="flex items-center space-x-4">
            <input
              type="checkbox"
              checked={selectedStyles.some(s => s.id === style.id)}
              onChange={(e) => {
                if (e.target.checked) {
                  setSelectedStyles([...selectedStyles, style]);
                } else {
                  setSelectedStyles(selectedStyles.filter(s => s.id !== style.id));
                }
              }}
              className="w-4 h-4 rounded"
            />
            <span className="text-white">{style.name}</span>
            {selectedStyles.some(s => s.id === style.id) && (
              <input
                type="range"
                min="0"
                max="100"
                value={selectedStyles.find(s => s.id === style.id).strength}
                onChange={(e) => updateStyleStrength(style.id, e.target.value)}
                className="flex-1"
              />
            )}
          </div>
        ))}
      </div>

      <button
        disabled={selectedStyles.length < 2}
        className="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 rounded-lg font-semibold"
        onClick={() => {
          // Here you would integrate with AI art generation
          console.log('Applying style fusion:', selectedStyles);
        }}
      >
        Apply Style Fusion
      </button>
    </div>
  );
};

export default StyleFusion;
