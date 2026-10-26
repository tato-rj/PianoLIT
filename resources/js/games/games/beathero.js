import { bootGame } from "../duel/DuelClient.js";
import { BeatHero } from "./beathero/BeatHero.js";

const options = window.__challengeOptions || {};
const clefUrls = window.__clefUrls || null;

bootGame((duelOptions) => new BeatHero({
  ...(duelOptions || options),
  clefUrls,
}));
