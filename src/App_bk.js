import React, { useEffect, useState } from 'react';
import './App.css';

function App() {
    const [posts, setPosts] = useState([]);

    useEffect(() => {
        fetch('http://localhost/my-headless-wp/wordpress/wp-json/wp/v2/posts')
            .then((response) => response.json())
            .then((data) => setPosts(data))
            .catch((error) => console.error('Error fetching posts:', error));
    }, []);

    return (
        <div className="App">
            <header className="App-header">
                <h1>WordPress Posts</h1>
                <ul>
                    {posts.map((post) => (
                        <li key={post.id}>
                            <h2>{post.title.rendered}</h2>
                            <div dangerouslySetInnerHTML={{ __html: post.content.rendered }} />
                        </li>
                    ))}
                </ul>
            </header>
        </div>
    );
}

export default App;
