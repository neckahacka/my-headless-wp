import React, { useState, useEffect } from 'react';
import { getPosts } from '../api'; // Import the API function
import PostCard from '../components/PostCard'; // Import the PostCard component

const HomePage = () => {
    const [posts, setPosts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    // Fetch posts on component mount
    useEffect(() => {
        const fetchPosts = async () => {
            try {
                const data = await getPosts();
                setPosts(data); // Set posts data
            } catch (err) {
                setError('Failed to load posts.');
                console.error(err);
            } finally {
                setLoading(false);
            }
        };

        fetchPosts();
    }, []);

    // Handle loading and error states
    if (loading) return <p>Loading posts...</p>;
    if (error) return <p>{error}</p>;

    return (
        <div>
            <h1>WordPress Posts</h1>
            <div className="posts-container">
                {posts.map((post) => (
                    <PostCard key={post.id} post={post} />
                ))}
            </div>
        </div>
    );
};

export default HomePage;
