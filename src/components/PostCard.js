import React from 'react';
import { Link } from 'react-router-dom';

const PostCard = ({ post }) => {
    console.log('Rendering PostCard:', post); // Debugging: Log each post
    return (
        <div className="post-card">
            <h2>{post.title.rendered}</h2>
            <p dangerouslySetInnerHTML={{ __html: post.excerpt.rendered }} />
            <Link to={`/post/${post.id}`}>Read More</Link>
        </div>
    );
};

export default PostCard;
