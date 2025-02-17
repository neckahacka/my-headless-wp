import React from 'react';
import StudioLayout from '../../components/studio/common/StudioLayout';
import BackgroundRemover from '../../components/studio/utils/BackgroundRemover';
import ImageEnhancer from '../../components/studio/utils/ImageEnhancer';
import FaceSwapper from '../../components/studio/utils/FaceSwapper';

const AITools = () => {
  return (
    <StudioLayout>
      <div className="space-y-8">
        <h1 className="text-3xl font-bold text-white mb-6">AI Tools</h1>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <ImageEnhancer />
          <BackgroundRemover />
          <FaceSwapper />
        </div>
      </div>
    </StudioLayout>
  );
};

export default AITools;
