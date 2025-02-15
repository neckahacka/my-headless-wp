import React from "react";
import { BrowserRouter as Router, Routes, Route } from "react-router-dom";
import { StudioProvider } from './contexts/StudioContext';
import Navbar from "./components/Navbar";
import HomePage from "./pages/HomePage";
import PostPage from "./pages/PostPage";
import StyleTransfer from "./pages/studio/StyleTransfer";
import CatPersonality from "./components/studio/creative/CatPersonality";  // ✅ Corrected
import StyleMixing from "./pages/studio/StyleMixing";
import Meowseum from "./pages/studio/Meowseum";
import ArtGenerator from "./components/studio/ArtGenerator"; // ✅ Corrected import path

const App = () => {
  return (
    <Router>
      <StudioProvider>
        <Navbar />
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/post/:id" element={<PostPage />} />
          <Route path="/studio/create" element={<ArtGenerator />} />  {/* Corrected path */}
          <Route path="/studio/transform" element={<StyleTransfer />} />
          <Route path="/studio/personality" element={<CatPersonality />} />
          <Route path="/studio/style-fusion" element={<StyleMixing />} />
          <Route path="/meowseum" element={<Meowseum />} />
          <Route path="*" element={<div>404 Not Found</div>} />
        </Routes>
      </StudioProvider>
    </Router>
  );
};

export default App;
