import React from 'react';

const StyleSelector = ({ value, onChange, disabled }) => {
  const styles = [
    { id: 'realistic', name: 'Realistic', description: 'Photorealistic style' },
    { id: 'cartoon', name: 'Cartoon', description: 'Playful cartoon style' },
    { id: 'anime', name: 'Anime', description: 'Anime-inspired art' },
    { id: 'painting', name: 'Painting', description: 'Digital painting style' },
  ];

  return (
    <div>
      <label className="block text-white mb-4">Choose Art Style</label>
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {styles.map((style) => (
          <button
            key={style.id}
            onClick={() => onChange(style.id)}
            disabled={disabled}
            className={p-4 rounded-lg text-left transition-all  disabled:opacity-50}
          >
            <div className="font-semibold mb-1">{style.name}</div>
            <div className="text-sm opacity-80">{style.description}</div>
          </button>
        ))}
      </div>
    </div>
  );
};

export default StyleSelector;
