// components/studio/AIFeatureMenu.jsx
import React from 'react';
import { 
  Wand2, 
  Palette, 
  Layers, 
  PersonStanding 
} from 'lucide-react';

const AIFeatureMenu = ({ onSelectFeature }) => {
  const features = [
    {
      id: 'cat-personality',
      name: 'Cat Personality Generator',
      icon: <PersonStanding />,
      description: 'Generate unique cat personalities for your art'
    },
    {
      id: 'style-fusion',
      name: 'Style Fusion',
      icon: <Layers />,
      description: 'Blend multiple artistic styles'
    },
    {
      id: 'background-magic',
      name: 'Background Magic',
      icon: <Wand2 />,
      description: 'AI-powered background generation'
    }
  ];

  return (
    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 p-4">
      {features.map((feature) => (
        <button
          key={feature.id}
          onClick={() => onSelectFeature(feature.id)}
          className="flex flex-col items-center p-4 bg-gray-800 rounded-lg hover:bg-gray-700 transition-colors"
        >
          <div className="text-purple-400 mb-2">{feature.icon}</div>
          <h3 className="text-white font-semibold mb-1">{feature.name}</h3>
          <p className="text-gray-400 text-sm text-center">{feature.description}</p>
        </button>
      ))}
    </div>
  );
};

export default AIFeatureMenu;