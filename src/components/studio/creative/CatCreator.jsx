import React from 'react';
import { Sparkles } from 'lucide-react';
import ArtGenerator from '../ArtGenerator';

const CatCreator = () => {
  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Sparkles className="w-6 h-6 text-purple-400" />
        <h2 className="text-2xl font-bold text-white">Cat Creator Studio</h2>
      </div>
      <ArtGenerator />
    </div>
  );
};

export default CatCreator;
