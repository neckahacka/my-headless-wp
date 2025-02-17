import React from 'react';
import { Link } from 'react-router-dom';

const FeatureMenu = () => {
  const features = [
    {
      id: 'cat-studio',
      title: 'HashCats Studio',
      description: 'Create and customize your own digital cat',
      path: '/studio/cats'
    },
    {
      id: 'ai-tools',
      title: 'AI Tools',
      description: 'Professional image editing tools',
      path: '/studio/tools'
    }
  ];

  return (
    <nav className="grid grid-cols-1 md:grid-cols-2 gap-4">
      {features.map(feature => (
        <Link
          key={feature.id}
          to={feature.path}
          className="p-6 bg-gray-800 rounded-lg hover:bg-gray-700 transition-colors"
        >
          <h3 className="text-xl font-bold text-white">{feature.title}</h3>
          <p className="text-gray-400">{feature.description}</p>
        </Link>
      ))}
    </nav>
  );
};

export default FeatureMenu;