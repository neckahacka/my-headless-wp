import React from 'react';
import { RefreshCw, Sparkles } from 'lucide-react';

const GenerationCanvas = ({ loading, error, imageUrl }) => {
  if (loading) {
    return (
      <div className="flex items-center justify-center h-96 bg-gray-700/50 rounded-lg">
        <div className="text-center text-gray-400">
          <RefreshCw className="w-8 h-8 mb-4 mx-auto animate-spin" />
          <p>Generating your unique HashCat...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center h-96 bg-gray-700/50 rounded-lg">
        <div className="text-center text-red-400">
          <p>Failed to generate image.</p>
          <p className="text-sm">Please try again.</p>
        </div>
      </div>
    );
  }

  if (!imageUrl) {
    return (
      <div className="flex items-center justify-center h-96 bg-gray-700/50 rounded-lg">
        <div className="text-center text-gray-400">
          <Sparkles className="w-8 h-8 mb-4 mx-auto" />
          <p>Your generated cat will appear here</p>
        </div>
      </div>
    );
  }

  return (
    <div className="relative h-96 bg-gray-700/50 rounded-lg overflow-hidden">
      <img
        src={imageUrl}
        alt="Generated cat art"
        className="w-full h-full object-contain"
      />
    </div>
  );
};

export default GenerationCanvas;
