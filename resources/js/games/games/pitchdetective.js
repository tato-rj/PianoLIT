import { bootGame } from "../duel/DuelClient.js";
import { PitchDetective } from "./pitchdetective/PitchDetective.js";

const options = window.__challengeOptions || {};
const clefUrls = window.__clefUrls || null;

bootGame((duelOptions) => new PitchDetective({
  ...(duelOptions || options),
  clefUrls,
}));
