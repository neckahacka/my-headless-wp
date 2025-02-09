import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { Link } from 'react-router-dom';
import { getPostById } from '../api';

const PostPage = () => {
  const { id } = useParams(); // Get the post ID from the route
  const [post, setPost] = useState(null); // Store post data
  const [loading, setLoading] = useState(true); // Loading state
  const [error, setError] = useState(null); // Error state

  useEffect(() => {
    const fetchPost = async () => {
      try {
        const data = await getPostById(id);

        // Fetch featured media if it exists
        if (data.featured_media) {
          const mediaResponse = await fetch(
            `http://localhost/my-headless-wp/wordpress/wp-json/wp/v2/media/${data.featured_media}`
          );
          const mediaData = await mediaResponse.json();
          data.featured_image = mediaData.source_url; // Add featured image to the post data
        }

        setPost(data); // Update the post state
      } catch (err) {
        console.error('Error fetching post:', err);
        setError('Sorry, we could not load the requested post.');
      } finally {
        setLoading(false); // Stop loading
      }
    };

    fetchPost();
  }, [id]);

  if (loading) return <div>Loading post...</div>; // Loading indicator
  if (error) return <div>{error}</div>; // Error message
  if (!post) return <div>The post you're looking for does not exist or has been removed.</div>; // No post found

  return (
    <div style={{ maxWidth: '800px', margin: '0 auto', padding: '20px' }}>
      <h1>{post.title.rendered}</h1>
      <div dangerouslySetInnerHTML={{ __html: post.content.rendered }} />
      {post.featured_image && (
        <img
          src={post.featured_image}
          alt={post.title.rendered}
          style={{ width: '100%', marginTop: '20px' }}
        />
      )}
      <Link to="/" style={{ display: 'block', marginTop: '20px', textDecoration: 'underline' }}>
        ← Back to Posts
      </Link>
    </div>
  );
};

export default PostPage;
