import React from 'react';
import ToolSelector from './ToolSelector';

const StudioLayout = ({ children }) => {
  return (
    <div className="min-h-screen bg-gray-900">
      <div className="container mx-auto px-4 py-8">
        <div className="flex gap-6">
          <aside className="w-64">
            <ToolSelector />
          </aside>
          <main className="flex-1">
            {children}
          </main>
        </div>
      </div>
    </div>
  );
};

export default StudioLayout;
