import React, { useState, useEffect } from 'react';
import { fetchPosts } from '../api';
import './styles.css';  // Add this import

const FeaturedArt = () => {
  const [artworks, setArtworks] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const loadArtworks = async () => {
      try {
        const posts = await fetchPosts();
        setArtworks(posts);
      } catch (error) {
        console.error('Error loading artworks:', error);
      } finally {
        setLoading(false);
      }
    };

    loadArtworks();
  }, []);

  if (loading) return <div>Loading featured artworks...</div>;

  return (
    <section className="featured-art">
      <h2>Featured AI Cat Art</h2>
      <div className="art-grid">
        {artworks.map((artwork) => (
          <div key={artwork.id} className="art-card">
            {artwork.featured_media ? (
              <img 
                src={artwork.featured_media} 
                alt={artwork.title.rendered} 
              />
            ) : (
              <div className="placeholder-image">No Image Available</div>
            )}
            <h3>{artwork.title.rendered}</h3>
          </div>
        ))}
      </div>
    </section>
  );
};

export default FeaturedArt;