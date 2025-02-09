import axios from 'axios';

const baseURL = 'http://localhost/my-headless-wp/wordpress/wp-json/wp/v2';

// Fetch all posts
export const fetchPosts = async () => {
  try {
    const response = await axios.get(`${baseURL}/posts`);
    return response.data;
  } catch (error) {
    console.error('Error fetching posts:', error);
    return [];
  }
};

// Fetch a single post by ID
export const getPostById = async (id) => {
  try {
    const response = await axios.get(`${baseURL}/posts/${id}`);
    return response.data;
  } catch (error) {
    console.error(`Error fetching post with ID ${id}:`, error);
    throw new Error('Post not found.');
  }
};
