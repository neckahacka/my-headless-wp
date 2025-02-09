import React, { useState } from "react";
import { Wand2 } from "lucide-react";

const CatStyleTransfer = () => {
 const [selectedImage, setSelectedImage] = useState(null);
 const [selectedStyle, setSelectedStyle] = useState(null);
 const [loading, setLoading] = useState(false);

 const styles = [
   { id: "watercolor", name: "Watercolor", description: "Soft watercolor effect" },
   { id: "oil-painting", name: "Oil Painting", description: "Classic oil painting style" },
   { id: "anime", name: "Anime", description: "Japanese anime style" },
   { id: "pop-art", name: "Pop Art", description: "Bold pop art style" }
 ];

 return (
   <div className="p-4 rounded-lg bg-gray-800">
     {styles.map((style) => (
       <button
         key={style.id}
         className="p-4 rounded-lg text-left transition-all"
         onClick={() => setSelectedStyle(style.id)}
       >
         <div className="font-semibold text-white">{style.name}</div>
         <div className="text-gray-400">{style.description}</div>
       </button>
     ))}
   </div>
 );
};

export default CatStyleTransfer;
