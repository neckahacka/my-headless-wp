import React from 'react';
import { useNavigate } from 'react-router-dom';

const ToolSelector = () => {
  const navigate = useNavigate();
  const tools = [
    { id: 'generator', name: 'Art Generator', path: '/studio/generator' },
    { id: 'transfer', name: 'Style Transfer', path: '/studio/transfer' }
  ];

  return (
    <nav className="space-y-2">
      {tools.map(tool => (
        <button
          key={tool.id}
          onClick={() => navigate(tool.path)}
          className="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors"
        >
          <span>{tool.name}</span>
        </button>
      ))}
    </nav>
  );
};

export default ToolSelector;
