import React, { createContext, useContext, useState } from 'react';

const StudioContext = createContext(null);

export const StudioProvider = ({ children }) => {
  const [activeTool, setActiveTool] = useState(null);
  const [settings, setSettings] = useState({});

  return (
    <StudioContext.Provider value={{
      activeTool,
      setActiveTool,
      settings,
      setSettings
    }}>
      {children}
    </StudioContext.Provider>
  );
};

export const useStudioContext = () => useContext(StudioContext);
