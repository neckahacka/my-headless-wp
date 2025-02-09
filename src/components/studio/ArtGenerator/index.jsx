import React, { useState } from "react";
import { Sparkles, Download, RefreshCw } from "lucide-react";

const ArtGenerator = () => {
  const [generatedImage, setGeneratedImage] = useState(null);
  const [error, setError] = useState(null);

  const handleDownload = async () => {
    if (!generatedImage) return;
    try {
      const response = await fetch(generatedImage);
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `hashcat-${Date.now()}.png`;  // Using backticks for template literal
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
    } catch (err) {
      setError("Failed to download image.");
    }
  };

  return (
    <div>
      {error && <div>{error}</div>}
      <button onClick={handleDownload}>Download</button>
    </div>
  );
};

export default ArtGenerator;
