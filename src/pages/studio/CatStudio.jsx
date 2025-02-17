import React from 'react';
import StudioLayout from '../../components/studio/common/StudioLayout';
import ArtGenerator from '../../components/studio/ArtGenerator';
import CatCreator from '../../components/studio/creative/CatCreator';
import CatStyleTransfer from '../../components/studio/creative/CatStyleTransfer';

const CatStudio = () => {
  return (
    <StudioLayout>
      <div className="space-y-8">
        <section>
          <h1 className="text-3xl font-bold text-white mb-6">HashCats Studio</h1>
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <CatCreator />
            <CatStyleTransfer />
          </div>
        </section>
        <section>
          <h2 className="text-2xl font-bold text-white mb-6">AI Art Generator</h2>
          <ArtGenerator />
        </section>
      </div>
    </StudioLayout>
  );
};

export default CatStudio;
