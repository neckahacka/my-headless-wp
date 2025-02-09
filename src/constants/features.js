import React from 'react';
import { Sparkles, Star, MessageSquare, Coins, Palette } from 'lucide-react';

export const CORE_FEATURES = [
  {
    icon: <Sparkles className="w-8 h-8" />,
    title: "HashCats Art Generation",
    description: "Transform your ideas into unique cat artwork with our AI technology"
  },
  {
    icon: <Palette className="w-8 h-8" />,
    title: "HashCats AI Generation",
    description: "Apply stunning artistic styles to your cat photos"
  },
  {
    icon: <MessageSquare className="w-8 h-8" />,
    title: "HashChat",
    description: "Get creative guidance from our AI companion"
  }
];

export const FUTURE_FEATURES = [
  {
    icon: <Sparkles className="w-5 h-5" />,
    title: "Advanced Style Mixing",
    description: "Blend multiple art styles to create unique masterpieces",
    learnMore: "/features/style-mixing"
  },
  {
    icon: <Star className="w-5 h-5" />,
    title: "Community Challenges",
    description: "Participate in themed contests and win exclusive rewards",
    learnMore: "/features/challenges"
  },
  {
    icon: <Coins className="w-5 h-5" />,
    title: "Digital Collectibles",
    description: "Turn your favorite creations into unique digital collectibles",
    learnMore: "/features/collectibles"
  }
];
