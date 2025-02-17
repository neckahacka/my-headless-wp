import React, { useState } from 'react';
import { Heart, Share2, Download } from 'lucide-react';

const Meowseum = () => {
 const [artworks] = useState([
   {
     id: 1,
     title: 'Space Explorer Cat',
     artist: 'CatArtist123',
     image: '/api/placeholder/400/400',
     likes: 245,
     category: 'AI Generated',
     style: 'Cosmic'
   },
   {
     id: 2,
     title: 'Steampunk Kitty',
     artist: 'PurrMaster',
     image: '/api/placeholder/400/400', 
     likes: 189,
     category: 'Style Transfer',
     style: 'Victorian'
   }
 ]);

 return (
   <div className="min-h-screen bg-[#1a1b26] p-8">
     <h1 className="text-4xl font-bold text-center mb-8 bg-gradient-to-r from-[#bf7af0] to-[#f690f4] text-transparent bg-clip-text">
       The Meowseum
     </h1>

     {/* Gallery Grid */}
     <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
       {artworks.map(art => (
         <div key={art.id} className="bg-gray-800 rounded-lg overflow-hidden hover:-translate-y-1 transition-transform">
           {/* Image */}
           <div className="aspect-square relative group">
             <img 
               src={art.image} 
               alt={art.title}
               className="w-full h-full object-cover"
             />
             {/* Overlay */}
             <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity">
               <div className="absolute bottom-0 left-0 right-0 p-4">
                 <h3 className="text-white font-semibold mb-1">{art.title}</h3>
                 <p className="text-gray-300 text-sm">by {art.artist}</p>
               </div>
             </div>
           </div>
           
           {/* Info Bar */}
           <div className="p-4 flex items-center justify-between">
             <div>
               <span className="text-purple-400 text-sm">{art.category}</span>
               <span className="text-gray-400 text-sm ml-2">•</span>
               <span className="text-gray-400 text-sm ml-2">{art.style}</span>
             </div>
             <div className="flex gap-3">
               <button className="text-gray-400 hover:text-purple-400">
                 <Heart className="w-5 h-5" />
               </button>
               <button className="text-gray-400 hover:text-purple-400">
                 <Share2 className="w-5 h-5" />
               </button>
               <button className="text-gray-400 hover:text-purple-400">
                 <Download className="w-5 h-5" />
               </button>
             </div>
           </div>
         </div>
       ))}
     </div>
   </div>
 );
};

export default Meowseum;
