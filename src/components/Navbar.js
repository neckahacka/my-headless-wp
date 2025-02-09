import React from "react";
import { Link } from "react-router-dom";

const Navbar = () => {
  return (
    <nav
      style={{
        padding: "1rem",
        background: "#333",
        color: "white",
        display: "flex",
        gap: "1rem",
      }}
    >
      <Link to="/" style={{ color: "white", textDecoration: "none" }}>
        Home
      </Link>
      <Link to="/post/1" style={{ color: "white", textDecoration: "none" }}>
        First Post
      </Link>
      <Link to="/studio/personality" style={{ color: "white", textDecoration: "none" }}>
        Cat Personality Generator
      </Link>
    </nav>
  );
};

export default Navbar;
