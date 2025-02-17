import React from "react";
import { Sparkles, Wand2, Star } from "lucide-react";

const HomePage = () => {
  return (
    <div className="min-h-screen bg-gradient-to-b from-gray-900 to-gray-800">
      <section className="relative overflow-hidden py-20">
        <div className="container mx-auto px-4">
          <div className="relative z-10 text-center max-w-4xl mx-auto">
            <h1 className="text-7xl font-bold mb-6 animate-gradient-y bg-clip-text text-transparent bg-gradient-to-r from-primary-500 via-secondary-500 to-primary-500">
              Where Feline Flair Meets Digital Innovation
            </h1>
            <p className="text-2xl text-gray-300 mb-12">
              Transform your feline friends into unique digital art masterpieces
            </p>
            <button className="px-8 py-4 bg-gradient-to-r from-primary-600 to-secondary-600 rounded-full text-white font-semibold hover:from-primary-500 hover:to-secondary-500 transform hover:-translate-y-1 transition-all">
              Start Creating
            </button>
          </div>

          {/* Animated background */}
          <div className="absolute inset-0 z-0">
            <div className="absolute w-96 h-96 bg-primary-500/20 rounded-full blur-3xl animate-pulse -top-20 -left-20" />
            <div className="absolute w-96 h-96 bg-secondary-500/20 rounded-full blur-3xl animate-pulse delay-1000 top-40 right-20" />
          </div>
        </div>
      </section>

      <section className="py-20">
        <div className="container mx-auto px-4">
          <div className="grid md:grid-cols-3 gap-8">
            {[
              {
                icon: <Sparkles className="w-10 h-10" />,
                title: "AI Art Generation",
                description: "Create unique artwork featuring your cat"
              },
              {
                icon: <Wand2 className="w-10 h-10" />,
                title: "Style Transfer",
                description: "Apply artistic styles to your cat photos"
              },
              {
                icon: <Star className="w-10 h-10" />,
                title: "Gallery",
                description: "Share and explore community creations"
              }
            ].map((feature, index) => (
              <div 
                key={index} 
                className="group bg-gray-800/50 backdrop-blur-sm p-8 rounded-2xl hover:bg-gray-700/50 transition-all hover:-translate-y-1 cursor-pointer"
              >
                <div className="text-primary-400 mb-6 group-hover:scale-110 transition-transform">
                  {feature.icon}
                </div>
                <h3 className="text-2xl font-semibold text-white mb-3">
                  {feature.title}
                </h3>
                <p className="text-gray-400 text-lg">
                  {feature.description}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>
    </div>
  );
};

export default HomePage;
