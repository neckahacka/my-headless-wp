import React from 'react';

const PromptBuilder = ({ value, onChange, disabled }) => {
  const suggestions = [
    'space explorer cat',
    'cyberpunk ninja cat',
    'magical wizard cat',
    'steampunk engineer cat',
  ];

  return (
    <div className="space-y-4">
      <div>
        <label className="block text-white mb-2">Describe Your Cat</label>
        <textarea
          value={value}
          onChange={(e) => onChange(e.target.value)}
          disabled={disabled}
          placeholder="Example: A majestic space explorer cat floating through a nebula..."
          className="w-full h-32 px-4 py-3 bg-gray-700 text-white rounded-lg focus:ring-2 focus:ring-purple-500 disabled:opacity-50"
        />
      </div>

      <div>
        <label className="block text-gray-400 mb-2 text-sm">Suggestions</label>
        <div className="flex flex-wrap gap-2">
          {suggestions.map((suggestion) => (
            <button
              key={suggestion}
              onClick={() => onChange(suggestion)}
              disabled={disabled}
              className="px-3 py-1 text-sm bg-gray-700 text-gray-300 rounded-full hover:bg-gray-600 disabled:opacity-50"
            >
              {suggestion}
            </button>
          ))}
        </div>
      </div>
    </div>
  );
};

export default PromptBuilder;
