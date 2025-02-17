import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { StudioProvider } from './contexts/StudioContext';
import CatStudio from './pages/studio/CatStudio';
import AITools from './pages/studio/AITools';
import AIArtGenerator from './pages/studio/AIArtGenerator';
import StyleTransfer from './pages/studio/StyleTransfer';
import CatPersonalityGenerator from './pages/studio/CatPersonalityGenerator';
import StyleMixing from './pages/studio/StyleMixing';
import Meowseum from './pages/studio/Meowseum';

const App = () => {
  return (
    <BrowserRouter>
      <StudioProvider>
        <Routes>
          <Route path="/" element={<CatStudio />} />
          <Route path="/studio" element={<CatStudio />} />
          <Route path="/tools" element={<AITools />} />
          <Route path="/studio/create" element={<AIArtGenerator />} />
          <Route path="/studio/transform" element={<StyleTransfer />} />
          <Route path="/studio/personality" element={<CatPersonalityGenerator />} />
          <Route path="/studio/style-fusion" element={<StyleMixing />} />
          <Route path="/meowseum" element={<Meowseum />} />
        </Routes>
      </StudioProvider>
    </BrowserRouter>
  );
};

export default App;
