import React from 'react';
import './styles.css';  // Add this import
const ToolsSection = () => {
  const tools = [
    {
      id: 1,
      title: "AI Art Generator",
      description: "Transform photos of your cat into unique artistic styles",
      icon: "🎨" // We can replace these with actual icons later
    },
    {
      id: 2,
      title: "Cat Personality Generator",
      description: "Create a unique AI-generated personality for your cat",
      icon: "😺"
    },
    {
      id: 3,
      title: "Community Gallery",
      description: "Share and explore cat art from our creative community",
      icon: "🖼️"
    }
  ];

  return (
    <section className="tools-section">
      <h2>Creative Tools</h2>
      <div className="tools-grid">
        {tools.map((tool) => (
          <div key={tool.id} className="tool-card">
            <div className="tool-icon">{tool.icon}</div>
            <h3>{tool.title}</h3>
            <p>{tool.description}</p>
            <button className="tool-button">Try Now</button>
          </div>
        ))}
      </div>
    </section>
  );
};

export default ToolsSection;